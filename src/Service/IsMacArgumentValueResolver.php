<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class IsMacArgumentValueResolver implements ArgumentValueResolverInterface
{

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return $argument->getName() === 'isMac';
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        if ($request->query->has('mac')) {
            if($request->query->get('mac')){
                yield $request->query->getBoolean('mac');
                return;
            }
        }
        $userAgent = $request->headers->get('User-Agent');
        yield stripos($userAgent,'Mac') !== false;
    }
}