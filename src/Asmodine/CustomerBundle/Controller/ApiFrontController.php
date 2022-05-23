<?php

namespace Asmodine\CustomerBundle\Controller;

use Asmodine\CommonBundle\Controller\Controller as AsmodineController;
use Asmodine\CommonBundle\DTO\PhysicalProfileDTO;
use Asmodine\CustomerBundle\Repository\PhysicalProfileRepository;
use Asmodine\SizeAdvisorBundle\Service\UserScoreService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiBackController.
 *
 * @Route("/api/front")
 */
class ApiFrontController extends AsmodineController
{
    /**
     * @Route("/physical_profile", name="asmodine.customer.api_front.user_profile")
     * @Method({"POST"})
     * @ApiDoc(
     *      section="API Front",
     *      resource=true,
     *      description="Update Physical Profile of User",
     *      tags={
     *          "1.0.0"
     *      },
     *      parameters={
     *          {"name"="physical_profile", "dataType"="string", "required"=true, "description"="api.front.parameters.description.physical_profile" },
     *      },
     *      statusCodes={
     *          Response::HTTP_ACCEPTED="Physical Profile was updated",
     *          Response::HTTP_PRECONDITION_FAILED="Parameter is empty",
     *          Response::HTTP_BAD_REQUEST="Error while updating physical profile",
     *          Response::HTTP_UNAUTHORIZED="Check API KEY",
     *          Response::HTTP_FORBIDDEN="Check API KEY",
     *     },
     *  )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function physicalProfileAction(Request $request)
    {
        $datasDTO = $request->get('physical_profile', null);
        $physicalProfileDTO = new PhysicalProfileDTO($datasDTO);
        if (is_null($physicalProfileDTO)) {
            return new JsonResponse('', Response::HTTP_PRECONDITION_FAILED);
        }

        /** @var UserScoreService $service */
        $service = $this->get('asmodine.size_advisor.user_score');

        /** @var PhysicalProfileRepository $repo */
        $repo = $this->get('asmodine.admin.repository.physical_profile');

        try {
            $old = $repo->getByUserId($physicalProfileDTO->userId);
            $repo->insertOrUpdate($physicalProfileDTO);
            $service->runIfDifferent($old, $physicalProfileDTO);

            return new JsonResponse('OK', Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $this->get('monolog.logger.asmodine_admin_front')->error(
                $e->getMessage(),
                ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]
            );

            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
